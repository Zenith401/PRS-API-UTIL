import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../config/config.dart';
import 'home_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';

class VerifyEmailScreen extends StatefulWidget {
  @override
  _VerifyEmailScreenState createState() => _VerifyEmailScreenState();
}

class _VerifyEmailScreenState extends State<VerifyEmailScreen> {
  String? _email;
  String? _token;
  bool _isVerified = false;
  bool _isLoading = true;
  String? _message;

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    setState(() {
      _email = prefs.getString('email'); // Get the logged-in user's email
      _token = prefs.getString('verification_token'); // Get the token from login
    });

    _checkVerificationStatus();
  }

  Future<void> _checkVerificationStatus() async {
    final response = await http.post(
      Uri.parse('http://${CONFIG.server}/PRS/flutter_util/check_email_status.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': _email}),
    );

    final responseData = json.decode(response.body);

    setState(() {
      _isVerified = responseData["is_verified"];
      _isLoading = false;
    });
  }

  Future<void> _verifyEmail() async {
    setState(() {
      _isLoading = true;
    });

    final response = await http.post(
      Uri.parse('http://${CONFIG.server}/PRS/flutter_util/verify_email.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'token': _token}),
    );

    final responseData = json.decode(response.body);

    setState(() {
      _message = responseData["message"];
      if (responseData["success"]) {
        _isVerified = true;
      }
      _isLoading = false;
    });

    // Refresh verification status
    _checkVerificationStatus();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Verify Email")),
      body: Center(
        child: _isLoading
            ? CircularProgressIndicator()
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    _isVerified ? Icons.check_circle : Icons.error,
                    color: _isVerified ? Colors.green : Colors.red,
                    size: 80,
                  ),
                  const SizedBox(height: 20),
                  Text(
                    _isVerified ? "Your email is verified!" : "Your email is not verified.",
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 18),
                  ),
                  if (!_isVerified)
                    Column(
                      children: [
                        SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _verifyEmail,
                          child: Text("Verify Email"),
                        ),
                      ],
                    ),
                  if (_message != null)
                    Padding(
                      padding: const EdgeInsets.all(8.0),
                      child: Text(
                        _message!,
                        style: TextStyle(
                          color: _isVerified ? Colors.green : Colors.red,
                        ),
                      ),
                    ),
                ],
              ),
      ),
    );
  }
}
