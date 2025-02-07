import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/config.dart';
import 'screens.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final TextEditingController _firstNameController = TextEditingController();
  final TextEditingController _lastNameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  double? _latitude;
  double? _longitude;

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _getCurrentLocation() async {
    try {
      Position position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high);
      setState(() {
        _latitude = position.latitude;
        _longitude = position.longitude;
      });
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text("Location Error: ${e.toString()}")),
      );
    }
  }

  Future<void> _submitForm() async {
  if (!_formKey.currentState!.validate()) return;

  setState(() {
    _isLoading = true;
  });

  await _getCurrentLocation();

  if (_latitude == null || _longitude == null) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Unable to get location. Please try again.')),
    );
    setState(() {
      _isLoading = false;
    });
    return;
  }

  final response = await http.post(
    Uri.parse('http://PRS/flutter_util/signup.php'),
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: {
      'fname': _firstNameController.text,
      'lname': _lastNameController.text,
      'email': _emailController.text,
      'password': _passwordController.text,
      'latitude': _latitude.toString(),
      'longitude': _longitude.toString(),
    },
  );

  final responseData = json.decode(response.body);
  setState(() {
    _isLoading = false;
  });

  if (responseData['success']) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text("Verify Your Email"),
        content: Text("We've sent a verification email. Please check your inbox."),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text("OK"),
          ),
        ],
      ),
    );
  } else {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Sign up failed: ${responseData['message']}')),
    );
  }
}


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sign Up')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              TextFormField(
                controller: _firstNameController,
                decoration: const InputDecoration(labelText: 'First Name'),
                validator: (value) => value!.isNotEmpty ? null : 'Enter your first name',
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _lastNameController,
                decoration: const InputDecoration(labelText: 'Last Name'),
                validator: (value) => value!.isNotEmpty ? null : 'Enter your last name',
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _emailController,
                decoration: const InputDecoration(labelText: 'Email'),
                validator: (value) => value!.contains('@') ? null : 'Enter a valid email',
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _passwordController,
                obscureText: true,
                decoration: const InputDecoration(labelText: 'Password'),
                validator: (value) => value!.length >= 6 ? null : 'Password must be 6+ chars',
              ),
              const SizedBox(height: 16),
              _isLoading
                  ? CircularProgressIndicator()
                  : ElevatedButton(
                      onPressed: _submitForm,
                      child: const Text('Sign Up'),
                    ),
            ],
          ),
        ),
      ),
    );
  }
}
