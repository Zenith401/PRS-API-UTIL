import 'package:cp2/screens/onboardingScreen.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/config.dart';
import 'homeScreen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  // Function to handle form submission
  Future<void> _submitForm() async {
    if (_formKey.currentState!.validate()) {
      final email = _emailController.text;
      final password = _passwordController.text;

      // Send the data to the PHP script
      final response = await http.post(
        Uri.parse('http://${CONFIG.server}/PRS/flutter_util/login.php'), // Ensure this URL is correct for your setup
        body: {
          'email': email,
          'password': password,
        },
      );

      // Print the raw response
      print('Response body: ${response.body}');

      // Check if the response is JSON
      try {
        final responseData = json.decode(response.body);
        if (responseData['success']) {
          // Save user session
          SharedPreferences prefs = await SharedPreferences.getInstance();
          await prefs.setString('user_id', responseData['user_id'].toString());

          // Navigate to home screen
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (context) => HomeScreen()),
          );
        } else {
          // Handle failed login
          print('Login failed: ${responseData['message']}');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Login failed: ${responseData['message']}')),
          );
        }
      } catch (e) {
        print('Error parsing JSON: $e');
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error parsing response from server')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Login Page'),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: <Widget>[
                TextFormField(
                  controller: _emailController,
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    hintText: 'Enter your Email',
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter a valid Email';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    hintText: 'Enter your Password',
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter password';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _submitForm,
                  child: const Text('Login'),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (context) => OnboardingScreen()),
                    );
                  },
                  child: const Text('Sign Up'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
